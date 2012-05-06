//
//  MasterViewController.m
//  Twitter Test
//
//  Created by Lasse Bunk on 1/8/12.
//  Copyright (c) 2012 __MyCompanyName__. All rights reserved.
//

#import "MasterViewController.h"
#import "DetailViewController.h"

@implementation MasterViewController
@synthesize barNumber;


- (void)awakeFromNib
{
    [super awakeFromNib];
}

- (void)didReceiveMemoryWarning
{
    [super didReceiveMemoryWarning];
    // Release any cached data, images, etc that aren't in use.
}

#pragma mark - View lifecycle

- (void)viewDidLoad
{
    [super viewDidLoad];
    barNumber = @"73125";
    
    [self fetchEvents];
}

- (void)fetchEvents
{
    dispatch_async(dispatch_get_global_queue(DISPATCH_QUEUE_PRIORITY_DEFAULT, 0), ^{
        NSString * myURL = @"http://localhost/~sebrenner/MyCourtDates/MyCourtDates.com/json.php?id=";

        myURL = [myURL stringByAppendingString: self->barNumber];
        NSData* data = [NSData dataWithContentsOfURL: 
                        [NSURL URLWithString: myURL]];
        
        
        NSError* error;

        events = [NSJSONSerialization JSONObjectWithData:data
                                                 options:kNilOptions 
                                                   error:&error];
        
        dispatch_async(dispatch_get_main_queue(), ^{
            [self.tableView reloadData];
        });
    });
}

- (NSInteger)tableView:(UITableView *)tableView numberOfRowsInSection:(NSInteger)section
{
    return events.count;
}

- (UITableViewCell *)tableView:(UITableView *)tableView cellForRowAtIndexPath:(NSIndexPath *)indexPath
{
    static NSString *CellIdentifier = @"TweetCell";
    
    UITableViewCell *cell = [tableView dequeueReusableCellWithIdentifier:CellIdentifier];
    if (cell == nil) {
        cell = [[UITableViewCell alloc] initWithStyle:UITableViewCellStyleDefault reuseIdentifier:CellIdentifier];
    }
    
    NSDictionary *event = [events objectAtIndex:indexPath.row]; 
    NSString *plnts = [event objectForKey:@"plaintiffs"];
    NSString *defs = [event objectForKey:@"defendants"];
    NSString *caption = [plnts capitalizedString];
    caption = [caption stringByAppendingString: @" v. "];
    caption = [caption stringByAppendingString:[defs capitalizedString]];

    NSString *setting = [event objectForKey:@"setting"];
    [setting capitalizedString];
    
    cell.textLabel.text = caption;
    cell.detailTextLabel.text = [NSString stringWithFormat:@"for %@", [setting capitalizedString]];
    
    return cell;
}



- (void)viewDidUnload
{
    [super viewDidUnload];
    // Release any retained subviews of the main view.
    // e.g. self.myOutlet = nil;
}

- (void)viewWillAppear:(BOOL)animated
{
    [super viewWillAppear:animated];
}

- (void)viewDidAppear:(BOOL)animated
{
    [super viewDidAppear:animated];
}

- (void)viewWillDisappear:(BOOL)animated
{
	[super viewWillDisappear:animated];
}

- (void)viewDidDisappear:(BOOL)animated
{
	[super viewDidDisappear:animated];
}

- (BOOL)shouldAutorotateToInterfaceOrientation:(UIInterfaceOrientation)interfaceOrientation
{
    // Return YES for supported orientations
    return (interfaceOrientation != UIInterfaceOrientationPortraitUpsideDown);
}

/*
// Override to support conditional editing of the table view.
- (BOOL)tableView:(UITableView *)tableView canEditRowAtIndexPath:(NSIndexPath *)indexPath
{
    // Return NO if you do not want the specified item to be editable.
    return YES;
}
*/

/*
// Override to support editing the table view.
- (void)tableView:(UITableView *)tableView commitEditingStyle:(UITableViewCellEditingStyle)editingStyle forRowAtIndexPath:(NSIndexPath *)indexPath
{
    if (editingStyle == UITableViewCellEditingStyleDelete) {
        // Delete the row from the data source.
        [tableView deleteRowsAtIndexPaths:[NSArray arrayWithObject:indexPath] withRowAnimation:UITableViewRowAnimationFade];
    } else if (editingStyle == UITableViewCellEditingStyleInsert) {
        // Create a new instance of the appropriate class, insert it into the array, and add a new row to the table view.
    }   
}
*/

/*
// Override to support rearranging the table view.
- (void)tableView:(UITableView *)tableView moveRowAtIndexPath:(NSIndexPath *)fromIndexPath toIndexPath:(NSIndexPath *)toIndexPath
{
}
*/

/*
// Override to support conditional rearranging of the table view.
- (BOOL)tableView:(UITableView *)tableView canMoveRowAtIndexPath:(NSIndexPath *)indexPath
{
    // Return NO if you do not want the item to be re-orderable.
    return YES;
}
*/

- (void)prepareForSegue:(UIStoryboardSegue *)segue sender:(id)sender
{
    if ([segue.identifier isEqualToString:@"showEvent"]) {
        
        NSInteger row = [[self tableView].indexPathForSelectedRow row];
        NSDictionary *event = [events objectAtIndex:row];
        
        DetailViewController *detailController = segue.destinationViewController;
        detailController.detailItem = event;
    }
}

- (IBAction)doAlertInput:(id)sender {
    UIAlertView *alertDialog;
    alertDialog = [[UIAlertView alloc]
        initWithTitle:@”Bar Number”
        message:@”Please enter your Bar Number:”
        delegate: self
        cancelButtonTitle: @”Ok”
        otherButtonTitles: nil];
    alertDialog.alertViewStyle=UIAlertViewStylePlainTextInput;
    [alertDialog show];}

- (void)alertView:(UIAlertView *)alertView
{
    clickedButtonAtIndex:(NSInteger)buttonIndex {
        NSString *buttonTitle=[alertView buttonTitleAtIndex:buttonIndex];
        if ([buttonTitle isEqualToString:@”Maybe Later”]) {
            self.userOutput.text=@”Clicked ‘Maybe Later’”;
        } else if ([buttonTitle isEqualToString:@”Never”]) {
            self.userOutput.text=@”Clicked ‘Never’”;
        }else{
            self.userOutput.text=@”Clicked ‘Ok’”;
        }
        if ([alertView.title isEqualToString: @”Email Address”]) {
            self.userOutput.text=[[alertView textFieldAtIndex:0] text];
        }
    }
}


@end
